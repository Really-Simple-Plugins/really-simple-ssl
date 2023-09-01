import * as React from "react";
const SvgIsIceland = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="IS_-_Iceland_svg__a"
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
    <g mask="url(#IS_-_Iceland_svg__a)">
      <path
        fill="#0061C1"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="IS_-_Iceland_svg__b"
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
      <g mask="url(#IS_-_Iceland_svg__b)">
        <path
          fill="#D21034"
          stroke="#F1F9FF"
          d="M5-.5h-.5v5h-5v3h5v5h3v-5h9v-3h-9v-5H5Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgIsIceland;
