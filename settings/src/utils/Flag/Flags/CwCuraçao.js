import * as React from "react";
const SvgCwCuraao = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CW_-_Cura\xE7ao_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#CW_-_Cura\xE7ao_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="CW_-_Cura\xE7ao_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#CW_-_Cura\xE7ao_svg__b)"
      >
        <path
          fill="#F7FCFF"
          d="m2.127 3.075-.994.524.48-.934L1 1.982l.762-.029L2.127 1l.28.953.89.029-.641.683.407.934-.936-.524ZM5.676 5.538l-1.227.514.481-1.288L3.863 3.9h1.279l.534-1.394.408 1.394h1.28l-.91.864.452 1.288-1.23-.514Z"
        />
        <path fill="#F9E813" d="M0 7v2h16V7H0Z" />
      </g>
    </g>
  </svg>
);
export default SvgCwCuraao;
