import * as React from "react";
const SvgCvCaboVerde = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CV_-_Cabo_Verde_svg__a"
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
    <g mask="url(#CV_-_Cabo_Verde_svg__a)">
      <path
        fill="#4141DB"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="CV_-_Cabo_Verde_svg__b"
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
      <g mask="url(#CV_-_Cabo_Verde_svg__b)">
        <path fill="#F90000" stroke="#F7FCFF" d="M0 6.5h-.5v2h17v-2H0Z" />
        <path
          stroke="#FFDE00"
          d="M5.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgCvCaboVerde;
