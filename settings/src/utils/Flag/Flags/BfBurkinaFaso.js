import * as React from "react";
const SvgBfBurkinaFaso = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BF_-_Burkina_Faso_svg__a"
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
    <g mask="url(#BF_-_Burkina_Faso_svg__a)">
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="BF_-_Burkina_Faso_svg__b"
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
        mask="url(#BF_-_Burkina_Faso_svg__b)"
      >
        <path fill="#C51918" d="M0 0v6h16V0H0Z" />
        <path
          fill="#FECA00"
          d="m8.018 7.885-2.352 1.78.753-2.899-2.206-1.764h2.629l1.175-2.573 1.176 2.573h2.629l-2.23 1.767.776 2.896-2.35-1.78Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgBfBurkinaFaso;
